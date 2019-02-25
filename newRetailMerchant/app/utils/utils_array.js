
function arr_contains(arr,item){

    for(let i = 0; i < arr.length;i ++){
        let temp = arr[i];
        if(temp == item){
            return true;
            break;
        }
    }
    return false;
}

export {
    arr_contains,
}